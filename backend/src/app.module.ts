import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { TypeOrmModule } from '@nestjs/typeorm';
import { AppController } from './app.controller';
import { AppService } from './app.service';

@Module({
  imports: [
    ConfigModule.forRoot({
      isGlobal: true,
      envFilePath: '.env',
    }),
    TypeOrmModule.forRoot({
      type: 'postgres',
      host: process.env.DB_HOST || 'postgres',
      port: parseInt(process.env.DB_PORT || '5432'),
      username: process.env.DB_USER || 'postgres',
      password: process.env.DB_PASSWORD || 'postgres',
      database: process.env.DB_NAME || 'aamevi_db',
      entities: ['dist/**/*.entity.js'],
      migrations: ['dist/database/migrations/*.js'],
      subscribers: ['dist/database/subscribers/*.js'],
      synchronize: false,
      logging: process.env.NODE_ENV === 'development',
    }),
    // Module imports will go here
    // AuthModule,
    // UsersModule,
    // CoursesModule,
    // etc...
  ],
  controllers: [AppController],
  providers: [AppService],
})
export class AppModule {}
